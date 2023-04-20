#!/bin/bash

set -e

echo "init test"
bin/developer ini:setup-tests-dbs -v
bin/developer phraseanet:regenerate-sqlite -v
bin/developer phraseanet:generate-js-fixtures -v

echo "do unit test"
vendor/phpunit/phpunit/phpunit  --exclude-group legacy
vendor/phpunit/phpunit/phpunit  --group legacy --exclude-group web
vendor/phpunit/phpunit/phpunit --group web
