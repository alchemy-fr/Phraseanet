#!/bin/bash

set -e
envsubst < "docker/phraseanet/php.ini.worker.sample" > /usr/local/etc/php/php.ini
cat docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf  | sed "s/\$REQUEST_TERMINATE_TIMEOUT/$REQUEST_TERMINATE_TIMEOUT/g" > /usr/local/etc/php-fpm.d/zz-docker.conf

if [[ -z "$PHRASEANET_APP_PORT" || $PHRASEANET_APP_PORT = "80" || $PHRASEANET_APP_PORT = "443" ]];then
export PHRASEANET_BASE_URL="$PHRASEANET_SCHEME://$PHRASEANET_HOSTNAME"
echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet BASE URL IS : " $PHRASEANET_BASE_URL 
else
export PHRASEANET_BASE_URL="$PHRASEANET_SCHEME://$PHRASEANET_HOSTNAME:$PHRASEANET_APP_PORT"
echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet BASE URL IS : " $PHRASEANET_BASE_URL
fi

maintenance_manager()
{
    if [[ $1 = "off" || $1 = "0" ]];then
            echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet No Maintenance Mode Activated"
            rm -rf /var/alchemy/Phraseanet/datas/nginx/maintenance.html
    
    elif [[ $1 = "on" || $1 = "1" ]];then
            echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Activating Maintenance Mode"
            mkdir -p /var/alchemy/Phraseanet/datas/nginx
            cp -Rf /usr/local/etc/maintenance.html /var/alchemy/Phraseanet/datas/nginx/maintenance.html
            if [[ $2 != "noexit" ]];then
                echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Maintenance in persitent Mode"
                exit 0
            else
                echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Maintenance in temporary Mode"
            fi
    else
            echo  `date +"%Y-%m-%d %H:%M:%S"` " - /!\ Phraseanet NULL value is not expected /!\ "
    fi
}

#if [[ $PHRASEANET_MAINTENANCE = "Off" ]];then
#        echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet No Maintenance Mode Activated"
#        rm -rf /var/alchemy/Phraseanet/datas/nginx/maintenance.html
#fi
#if [[ $PHRASEANET_MAINTENANCE = "On" ]];then
#        echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Activating Maintenance Mode"
#        mkdir -p /var/alchemy/Phraseanet/datas/nginx
#        cp -Rf /usr/local/etc/maintenance.html /var/alchemy/Phraseanet/datas/nginx/maintenance.html
#fi

maintenance_manager $PHRASEANET_MAINTENANCE

echo "creating config subderectories overwritten by the config pvc"

mkdir -p config/plugins
mkdir -p config/wm
mkdir -p config/status
mkdir -p config/minilogos
mkdir -p config/templates/web
mkdir -p config/templates/mobile
mkdir -p config/stamp
mkdir -p config/custom_files
mkdir -p config/presentation
mkdir -p config/topics

echo "Ended creating config subderectories overwritten by the config pvc"

FILE=config/configuration.yml

if [[ ! -f "$FILE"  && $PHRASEANET_INSTALL = 1 ]];then
    echo  `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet $FILE doesn't exist, Play Phraseanet installation..."
    maintenance_manager 1 noexit

    chown app:app \
        cache \
        config \
        tmp \
        logs \
        www \
        datas

    runuser app -c docker/phraseanet/setup/auto-install.sh
    chmod 600 config/configuration.yml
    PHRASEANET_UPGRADE=0
    echo `date +"%Y-%m-%d %H:%M:%S"` " - End of Phraseanet Installation"
    maintenance_manager 0

fi

if [[ -f "$FILE" && $PHRASEANET_UPGRADE = 1 ]];then
   maintenance_manager 1 noexit
   echo `date +"%Y-%m-%d %H:%M:%S"` " - preparing config backup, check connection to db "
   bin/console system:clear-cache
   bin/console system:clear-session
   timestamp=$(date +'%Y-%m-%d_%H-%M-%S')
   timestamp_dir="backup/pre-upgrade/$timestamp"
   mkdir -p "$timestamp_dir"
   archive_name="$PHRASEANET_HOSTNAME-config.tgz"
   tar -zcf "$timestamp_dir/$archive_name" -C "config" .
   echo `date +"%Y-%m-%d %H:%M:%S"` " - Pre-upgrade backup done for config  $timestamp_dir/$archive_name"
   echo `date +"%Y-%m-%d %H:%M:%S"` " - Start Phraseanet upgrade datas"
   bin/setup system:upgrade -y
   echo `date +"%Y-%m-%d %H:%M:%S"` " - End Phraseanet upgrade datas"
   maintenance_manager 0

fi

if [[ -f "$FILE" && $PHRASEANET_SETUP = 1 ]]; then
    echo `date +"%Y-%m-%d %H:%M:%S"` " - $FILE exists, start setup ."
    maintenance_manager 1 noexit

    if [[ $PHRASEANET_PROJECT_NAME && $ENV_SET_PHRASEANET_PROJECT_NAME == 1 ]]; then
        bin/setup system:config set -q registry.general.title "$PHRASEANET_PROJECT_NAME"
        echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Title is set to $PHRASEANET_PROJECT_NAME"
    else
        echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet instance name is NOT set to $PHRASEANET_PROJECT_NAME because ENV_SET_PHRASEANET_PROJECT_NAME is set to $ENV_SET_PHRASEANET_PROJECT_NAME "
    fi

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Static URL PHRASEANET_BASE_URL"
    bin/setup system:config set -q servername $PHRASEANET_BASE_URL

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Setting available language in GUI and search"
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
    
    bin/setup system:config set -q languages.default $PHRASEANET_DEFAULT_LANGUAGE

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

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting session type"

    if [[ $SESSION_SAVE_HANDLER == file ]]; then
        bin/setup system:config set main.session.type "$SESSION_SAVE_HANDLER"

        echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet PHP session manager is $SESSION_SAVE_HANDLER"
    else
        bin/setup system:config set main.session.type "native"
        echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet PHP session manager is Native by redis"
    fi

    ## Phraseanet application Database setting

    echo `date +"%Y-%m-%d %H:%M:%S"` - "Overwriting Phraseanet Database connexion informations"

    bin/setup system:config set -q main.database.host $PHRASEANET_DB_HOST
    bin/setup system:config set -q main.database.port $PHRASEANET_DB_PORT
    bin/setup system:config set -q main.database.user $PHRASEANET_DB_USER
    bin/setup system:config set -q main.database.password $PHRASEANET_DB_PASSWORD
    bin/setup system:config set -q main.database.dbname $INSTALL_APPBOX

    ## Phraseanet application cache setting
    echo `date +"%Y-%m-%d %H:%M:%S"` - "Setting up for Phraseanet cache"
    echo `date +"%Y-%m-%d %H:%M:%S"` - "Cache Type is $PHRASEANET_CACHE_TYPE"
    bin/setup system:config set -q main.cache.options.host $PHRASEANET_CACHE_HOST
    bin/setup system:config set -q main.cache.options.port $PHRASEANET_CACHE_PORT
    bin/setup system:config set -q main.cache.options.namespace $PHRASEANET_HOSTNAME
    bin/setup system:config set -q main.cache.type $PHRASEANET_CACHE_TYPE

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


    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting RABBITMQ"
    bin/setup system:config set workers.queue.worker-queue.host $PHRASEANET_RABBITMQ_HOST
    bin/setup system:config set workers.queue.worker-queue.port $PHRASEANET_RABBITMQ_PORT
    bin/setup system:config set workers.queue.worker-queue.vhost $PHRASEANET_RABBITMQ_VHOST
    bin/setup system:config set workers.queue.worker-queue.ssl $PHRASEANET_RABBITMQ_SSL
    bin/setup system:config set workers.queue.worker-queue.heartbeat $PHRASEANET_RABBITMQ_HEARTBEAT
    bin/setup system:config set -q workers.queue.worker-queue.user $PHRASEANET_RABBITMQ_USER
    bin/setup system:config set -q workers.queue.worker-queue.password $PHRASEANET_RABBITMQ_PASSWORD
    
    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting Download Async"
    if [ -z "$PHRASEANET_DOWNLOAD_ASYNC" ]; then
        echo "$(date +"%Y-%m-%d %H:%M:%S")  - DownloadAsync not set, PHRASEANET_DOWNLOAD_ASYNC is null "
    else
    bin/setup system:config set download_async.enabled $PHRASEANET_DOWNLOAD_ASYNC
    fi 

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Define external service Pusher"
    if [ -z "$PUSHER_APP_ID" ]; then
        echo "$(date +"%Y-%m-%d %H:%M:%S")  - Pusher service not defined, PUSHER_APP_ID is null"
    else
        bin/setup system:config set -q externalservice.pusher.auth_key $PUSHER_AUTH_KEY
        bin/setup system:config set -q externalservice.pusher.secret $PUSHER_SECRET
        bin/setup system:config set -q externalservice.pusher.app_id $PUSHER_APP_ID
    fi



    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet setting SMTP "
    if [[ $PHRASEANET_SMTP_ENABLED && $PHRASEANET_SMTP_ENABLED = true ]]; then
        bin/setup system:config set -q registry.email.smtp-enabled $PHRASEANET_SMTP_ENABLED
        bin/setup system:config set -q registry.email.smtp-auth-enabled $PHRASEANET_SMTP_AUTH_ENABLED
        bin/setup system:config set -q registry.email.smtp-secure-mode $PHRASEANET_SMTP_SECURE_MODE
        bin/setup system:config set -q registry.email.smtp-host $PHRASEANET_SMTP_HOST
        bin/setup system:config set -q registry.email.smtp-port $PHRASEANET_SMTP_PORT
        bin/setup system:config set -q registry.email.smtp-user $PHRASEANET_SMTP_USER
        bin/setup system:config set -q registry.email.smtp-password $PHRASEANET_SMTP_PASSWORD
        bin/setup system:config set -q registry.email.emitter-email $PHRASEANET_EMITTER_EMAIL
        bin/setup system:config set -q registry.email.prefix "$PHRASEANET_MAIL_OBJECT_PREFIX"
    fi

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Root account sync"
    if [[ -n ${PHRASEANET_ADMIN_ACCOUNT_ID} && $PHRASEANET_ADMIN_ACCOUNT_ID =~ ^[0-9]+$ ]]; then
       bin/console user:edit --user_id=$PHRASEANET_ADMIN_ACCOUNT_ID --login $PHRASEANET_ADMIN_ACCOUNT_EMAIL --email $PHRASEANET_ADMIN_ACCOUNT_EMAIL --password $PHRASEANET_ADMIN_ACCOUNT_PASSWORD -y
       echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet Root account synced"
    fi
    
    echo `date +"%Y-%m-%d %H:%M:%S"` " - config/configuration.yml update by Phraseanet entrypoint.sh Finished !"
    maintenance_manager 0
fi

maintenance_manager 1 noexit

echo `date +"%Y-%m-%d %H:%M:%S"` " - Init plugin install "
./docker/phraseanet/plugins/console init

if [ -d "plugins/" ];then
chown -R app:app plugins;
fi

echo `date +"%Y-%m-%d %H:%M:%S"` " - Flushing application cache"
rm -Rf cache/*

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on cache/ repository"
chown -R app:app cache 

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on config/ repository"
chown -R app:app config

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on tmp/ repository"
chown -R app:app tmp

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on logs/ repository"
chown -R app:app logs

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on ftp/ repository"
chown -R app:app ftp

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on backup/ repository"
chown -R app:app backup

echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on www/ repository"
chown -R app:app www
    
echo `date +"%Y-%m-%d %H:%M:%S"` " - End of chown!"   

echo `date +"%Y-%m-%d %H:%M:%S"` " - End of Phraseanet setup entrypoint.sh"

maintenance_manager 0

bash -e docker-php-entrypoint $@
