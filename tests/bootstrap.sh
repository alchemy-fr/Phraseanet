#!/usr/bin/env bash

set -e

if ! ./bin/developer system:uninstall > /dev/null 2>&1
then
  rm -f config/configuration.yml config/configuration-compiled.php
fi
./bin/setup system:install --email=test@phraseanet.com --password=test --db-user=root --db-template=fr --db-password=toor --databox=db_test --appbox=ab_test --server-name=http://127.0.0.1 -y -vvv
./bin/developer ini:reset --email=test@phraseanet.com --password=test --run-patches --no-setup-dbs -vvv
php resources/hudson/cleanupSubdefs.php
./bin/developer ini:setup-tests-dbs -vvv
./bin/developer phraseanet:regenerate-sqlite -vvv
