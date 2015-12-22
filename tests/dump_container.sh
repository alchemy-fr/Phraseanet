#!/usr/bin/env sh

set +x

rm -f pimple.json;
APP_ENV=dev APP_CONTAINER_DUMP=allowed php -S 0.0.0.0:8080 www/index_dev.php &
SERVER_PID=$!
sleep 1;
curl http://127.0.0.1:8080/_dump
kill $SERVER_PID

