#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
envsubst < "docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf" > /usr/local/etc/php-fpm.d/zz-docker.conf

chown -R app:app \
    cache \
    config \
    datas \
    tmp \
    logs \
    www/thumbnails \
    www/custom

FILE=config/configuration.yml

if [ -f "$FILE" ]; then
    echo "$FILE exists, skip setup."
    bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME
    if [[ $PHRASEANET_SMTP_ENABLED=true ]]; then
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
    bin/console user:password --user_id=1 --password $PHRASEANET_ADMIN_ACCOUNT_PASSWORD -y
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c docker/phraseanet/auto-install.sh
fi

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

./docker/phraseanet/plugins/console init

bash -e docker-php-entrypoint $@
