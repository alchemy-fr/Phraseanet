#!/usr/bin/env bash

set -e

mysql -uroot -ptoor -e 'SET @@global.sql_mode=STRICT_ALL_TABLES;'
if ! ./bin/developer system:uninstall > /dev/null 2>&1
then
    rm -f config/configuration.yml config/configuration-compiled.php
fi
./bin/setup system:install --email=test@phraseanet.com --password=test --db-user=root --db-template=fr --db-password=toor --databox=db_test --appbox=ab_test --server-name=http://127.0.0.1 -y -vvv
case "$1" in
    update)
        ./bin/developer ini:reset --email=test@phraseanet.com --password=test --run-patches --no-setup-dbs -vvv
        php resources/hudson/cleanupSubdefs.php
        ;;
esac
./bin/developer ini:setup-tests-dbs -vvv
./bin/console searchengine:index:create
./bin/developer phraseanet:regenerate-sqlite -vvv
