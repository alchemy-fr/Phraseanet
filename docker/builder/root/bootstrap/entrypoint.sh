#!/bin/bash

if [ -d /bootstrap/entrypoint.d ]; then
  for i in /bootstrap/entrypoint.d/*.sh; do
    if [ -r $i ]; then
      . $i
    fi
  done
  unset i
fi

if [ ! -t 1 ] ; then
  echo "No tty available."
  exit 0
fi

exec "$@"
