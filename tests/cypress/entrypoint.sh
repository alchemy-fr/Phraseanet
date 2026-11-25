#!/bin/sh
# Starts the optional localhost relay, then runs Cypress with the given arguments.
#
# Why a relay: a local Phraseanet served over plain HTTP may still send its session
# cookie with the "Secure" flag. Browsers accept such a cookie on http://localhost
# only. Relaying localhost:<port> to the instance gives the browser the same origin
# as on the developer's machine.
#
# PHRASEANET_RELAY=host:port  -> listen on localhost:<port> and forward to host:port
set -e

if [ -n "$PHRASEANET_RELAY" ]; then
  port="${PHRASEANET_RELAY##*:}"
  socat "TCP-LISTEN:${port},fork,reuseaddr,bind=127.0.0.1" "TCP:${PHRASEANET_RELAY}" &
  echo "Relay localhost:${port} -> ${PHRASEANET_RELAY}"
fi

exec cypress run "$@"
