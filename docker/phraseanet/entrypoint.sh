#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
cat docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf  | sed "s/\$REQUEST_TERMINATE_TIMEOUT/$REQUEST_TERMINATE_TIMEOUT/g" > /usr/local/etc/php-fpm.d/zz-docker.conf



FILE=config/configuration.yml

if [[ ! -f "$FILE"  && $PHRASEANET_INSTALL = 1 ]];then
    echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet $FILE doesn't exist, Play Phraseanet installation..."
    chown app:app \
        cache \
        config \
        tmp \
        logs \
        www \
        datas

    runuser app -c docker/phraseanet/auto-install.sh
    chmod 600 config/configuration.yml
   echo `date +"%Y-%m-%d %H:%M:%S"` " - End of Phraseanet Installation"

fi

if [[ -f "$FILE" && $PHRASEANET_UPGRADE = 1 ]];then
   echo `date +"%Y-%m-%d %H:%M:%S"` " - Start Phraseanet upgrade datas"
   # TODO check before if an upgrade is require
   bin/setup system:upgrade -y
   echo `date +"%Y-%m-%d %H:%M:%S"` " - End Phraseanet upgrade datas"
fi

if [[ -f "$FILE" && $PHRASEANET_SETUP = 1 ]]; then
    echo `date +"%Y-%m-%d %H:%M:%S"` " - $FILE exists, start setup ."
    
    if [[ $PHRASEANET_PROJECT_NAME && $ENV_SET_PHRASEANET_PROJET_NAME == 1 ]]; then
        bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME
    fi

    echo `date +"%Y-%m-%d %H:%M:%S"` " -  Phraseanet Setting available language in GUI and search"
    counter=0 
    if [[ -n $PHRASEANET_AVAILABLE_LANGUAGE ]]; then
        for i in $(echo $PHRASEANET_AVAILABLE_LANGUAGE | sed "s/,/ /g")
            do
                counter=$(( counter+1 ))
                if [[ $counter -eq 1 ]] ; then
                    bin/setup system:config set languages.available $i
                    bin/setup system:config add languages.available $i
                else
                    bin/setup system:config add languages.available $i   
                fi
            done
    fi
    
    bin/setup system:config set languages.default $PHRASEANET_DEFAULT_LANGUAGE

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting for Trusted Proxies"
    counter=0 
    if [[ -n $PHRASEANET_TRUSTED_PROXIES ]]; then
        for i in $(echo $PHRASEANET_TRUSTED_PROXIES | sed "s/,/ /g")
            do
                counter=$(( counter+1 ))
                if [[ $counter -eq 1 ]] ; then
                    bin/setup system:config set trusted-proxies $i
                    bin/setup system:config add trusted-proxies $i
                else
                    bin/setup system:config add trusted-proxies $i   
                fi
            done
    fi

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting for debugger allowed IP"
    counter=0 
    if [[ -n $PHRASEANET_DEBUG_ALLOWED_IP ]]; then
        for i in $(echo $PHRASEANET_DEBUG_ALLOWED_IP | sed "s/,/ /g")
            do
                counter=$(( counter+1 ))
                if [[ $counter -eq 1 ]] ; then
                    bin/setup system:config set debugger.allowed-ips $i
                    bin/setup system:config add debugger.allowed-ips $i
                else
                    bin/setup system:config add debugger.allowed-ips $i   
                fi
            done
    fi

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting external Binaries timeout "
    bin/setup system:config set main.binaries.ffmpeg_timeout $PHRASEANET_FFMPEG_TIMEOUT
    bin/setup system:config set main.binaries.ffprobe_timeout $PHRASEANET_FFPROBE_TIMEOUT
    bin/setup system:config set main.binaries.gs_timeout $PHRASEANET_GS_TIMEOUT
    bin/setup system:config set main.binaries.mp4box_timeout $PHRASEANET_MP4BOX_TIMEOUT
    bin/setup system:config set main.binaries.swftools_timeout $PHRASEANET_SWFTOOLS_TIMEOUT
    bin/setup system:config set main.binaries.unoconv_timeout $PHRASEANET_UNOCON_TIMEOUT
    bin/setup system:config set main.binaries.exiftool_timeout $PHRASEANET_EXIFTOOL_TIMEOUT
    
    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting API "
    bin/setup system:config set registry.api-clients.api-enabled $PHRASEANET_API_ENABLED
    bin/setup system:config set registry.api-clients.api-require-ssl $PHRASEANET_API_SSL
    bin/setup system:config set registry.api-clients.api-auth-token-header-only $PHRASEANET_API_AUTH_TOKEN_HEADER_ONLY

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting SMTP "
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
    
    # TODO define mapbox setting
    # echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting Mapbox "
    

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet root account Password sync"
    if [[ -n ${PHRASEANET_ADMIN_ACCOUNT_ID} && $PHRASEANET_ADMIN_ACCOUNT_ID =~ ^[0-9]+$ ]]; then
       bin/console user:password --user_id=$PHRASEANET_ADMIN_ACCOUNT_ID --password $PHRASEANET_ADMIN_ACCOUNT_PASSWORD -y
    fi
    
    echo `date +"%Y-%m-%d %H:%M:%S"` " - config/configuration.yml update by Phraseanet entrypoint.sh Finished !"
fi

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
  rm /usr/local/etc/php/conf.d/newrelic.ini 
fi

if [[ $BLACKFIRE_ENABLED = "true" ]]; then
  echo `date +"%Y-%m-%d %H:%M:%S"` " - BlackFire setup."
  blackfire-agent --register --server-id=$BLACKFIRE_SERVER_ID --server-token=$BLACKFIRE_SERVER_TOKEN
  service blackfire-agent start
  echo "Blackfire setup done"
else
    echo `date +"%Y-%m-%d %H:%M:%S"` " - blackfire extension deactivation."
    rm /usr/local/etc/php/conf.d/zz-blackfire.ini
fi


./docker/phraseanet/plugins/console init
rm -Rf cache/*


chown -R app:app \
    cache \
    config \
    tmp \
    logs \
    www
    

if [ -d "plugins/" ];then
chown -R app:app plugins;
fi

chown -R app:app datas && echo `date +"%Y-%m-%d %H:%M:%S"` " - Finished chown on datas by entrypoint" &
echo `date +"%Y-%m-%d %H:%M:%S"` " - End of Phraseanet entrypoint.sh"

bash -e docker-php-entrypoint $@
